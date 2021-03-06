<?php

namespace Hexanet\Swiftmailer;

use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Image;
use Swift_Mime_Message;
use Swift_Mime_MimeEntity;

class ImageEmbedPlugin implements Swift_Events_SendListener
{

    /**
     * @param Swift_Events_SendEvent $event
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        if ($message->getContentType() === 'text/html') {
            $message->setBody($this->embedImages($message));
        }

        foreach ($message->getChildren() as $part) {
            if (strpos($part->getContentType(), 'text/html') === 0) {
                $part->setBody($this->embedImages($message, $part), 'text/html');
            }
        }
    }

    /**
     * @param Swift_Events_SendEvent $event
     */
    public function sendPerformed(Swift_Events_SendEvent $event)
    {

    }

    /**
     * @param Swift_Mime_Message $message
     * @param Swift_Mime_MimeEntity $part
     *
     * @return string
     */
    protected function embedImages(Swift_Mime_Message $message, Swift_Mime_MimeEntity $part = null)
    {
        $body = $part === null ? $message->getBody() : $part->getBody();

        $dom = new \DOMDocument('1.0');
        $dom->loadHTML($body);

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            $src = $image->getAttribute('src');

            /**
             * Prevent beforeSendPerformed called twice
             * see https://github.com/swiftmailer/swiftmailer/issues/139
             */
            if (strpos($src, 'cid:') === false) {

                $entity = \Swift_Image::fromPath($src);
                $message->setChildren(
                    array_merge($message->getChildren(), [$entity])
                );

                $image->setAttribute('src', 'cid:' . $entity->getId());
            }
        }

        return $dom->saveHTML();
    }
}